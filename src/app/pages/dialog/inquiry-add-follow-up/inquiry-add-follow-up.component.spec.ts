import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { InquiryAddFollowUpComponent } from './inquiry-add-follow-up.component';

describe('InquiryAddFollowUpComponent', () => {
  let component: InquiryAddFollowUpComponent;
  let fixture: ComponentFixture<InquiryAddFollowUpComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ InquiryAddFollowUpComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(InquiryAddFollowUpComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
